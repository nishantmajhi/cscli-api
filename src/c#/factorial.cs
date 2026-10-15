using System;

class Factorial
{
    static long Compute(int n)
    {
        long product = 1;
        for (int i = n; i > 1; i--)
        {
            product *= i;
        }
        return product;
    }

    static void Main()
    {
        Console.Write("Enter a number: ");
        int num = Convert.ToInt32(Console.ReadLine());

        if (num < 0)
        {
            Console.WriteLine("Factorial of negative number can't be calculated!");
            return;
        }

        Console.WriteLine("Factorial of " + num + " = " + Compute(num));
    }
}
